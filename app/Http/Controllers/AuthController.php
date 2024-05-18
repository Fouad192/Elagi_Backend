<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Product;
use App\Models\Medicine;
use App\Models\Contact;
use App\Models\Favorite;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Donation;
use App\Models\Feedback;
use App\Models\RareMedicineRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Notification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use App\Notifications\OtpNotification;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use App\Mail\ResetPasswordOtpMail;
use Tymon\JWTAuth\Facades\JWTAuth;
use Tymon\JWTAuth\Exceptions\JWTException;
use thiagoalessio\TesseractOCR\TesseractOCR;
use Stripe\Stripe;
use Stripe\PaymentIntent;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class AuthController extends Controller
{

    public function register(Request $request)
        {
            // Define validation rules
            $rules = [
                'fullname' => ['required', 'regex:/^[a-zA-Z\s]+$/'],
                'email' => 'required|email|ends_with:@gmail.com|unique:users,email',
                'phone' => 'required|digits:11',
                'password' => 'required|min:8|confirmed',
            ];

            // Define custom messages for each validation rule
            $customMessages = [
                'fullname.required' => 'Full name is required.',
                'fullname.regex' => 'Full name must contain letters and spaces only.',
                'email.required' => 'Email is required.',
                'email.email' => 'Email must be a valid email address.',
                'email.ends_with' => 'Email must end with @gmail.com.',
                'email.unique' => 'This email address is already in use.',
                'phone.required' => 'Phone number is required.',
                'phone.digits' => 'Phone number must be exactly 11 digits long.',
                'password.required' => 'Password is required.',
                'password.min' => 'Password must be at least 8 characters long.',
                'password.confirmed' => 'Passwords do not match.',
            ];

            // Perform validation
            $validator = Validator::make($request->all(), $rules, $customMessages);

            if ($validator->fails()) {
                return response()->json(['errors' => $validator->errors()], 400);
            }

            // Generate OTP and set expiration
            $otp = rand(100000, 999999);
            $expiresAt = now()->addMinutes(10);

            // Prepare user data for caching or further processing
            $userData = [
                'name' => $request->fullname,
                'email' => $request->email,
                'phone' => $request->phone,
                'password' => $request->password, // Remember to hash the password
            ];

            // Cache user data along with the OTP
            $cacheKey = 'otp_verification_' . $request->email;
            Cache::put($cacheKey, ['user' => $userData, 'otp' => $otp], $expiresAt);

            // Send OTP via email
            Notification::route('mail', $request->email)->notify(new OtpNotification($otp));

            // Respond with a success message
            return response()->json(['message' => 'OTP sent to email. Please verify to complete registration.']);
        }

    public function resendOtp(Request $request)
        {
            $request->validate(['email' => 'required|email']);

            // Fetch existing cached registration data
            $cacheKey = 'otp_verification_' . $request->email;
            $cachedData = Cache::get($cacheKey);

            if (!$cachedData) {
                return response()->json(['message' => 'No registration data found for the provided email.'], 404);
            }

            // Regenerate OTP
            $otp = rand(100000, 999999);
            $expiresAt = now()->addMinutes(10);

            // Update cache with new OTP while retaining the user's data
            $cachedData['otp'] = $otp;
            Cache::put($cacheKey, $cachedData, $expiresAt);

            // Resend the OTP via email
            Notification::route('mail', $request->email)->notify(new OtpNotification($otp));

            return response()->json(['message' => 'OTP has been resent to your email.']);
        }

    public function verifyOtp(Request $request)
        {
            $request->validate([
                'email' => 'required|email',
                'otp' => 'required|numeric',
            ]);

            $cacheKey = 'otp_verification_' . $request->email;
            $cachedData = Cache::get($cacheKey);

            if (!$cachedData) {
                Log::error("OTP verification failed: Cache data not found.", ['email' => $request->email]);
                return response()->json(['message' => 'OTP verification failed. Data not found.'], 400);
            }

            if ($cachedData['otp'] != $request->otp) {
                Log::error("OTP verification failed: OTP mismatch.", ['email' => $request->email, 'cached_otp' => $cachedData['otp'], 'provided_otp' => $request->otp]);
                return response()->json(['message' => 'Invalid or expired OTP.'], 400);
            }

            // Proceed with user creation, now hashing the password
            $userData = $cachedData['user'];
            $userData['password'] = Hash::make($userData['password']); // Hash password just before saving
            $user = User::create($userData);

            Cache::forget($cacheKey);

            return response()->json(['message' => 'OTP verified successfully. Account created.']);
        }


    public function login(Request $request)
        {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
            ]);

            // Retrieve the user by email
            $user = User::where('email', $request->email)->first();

            // Check if user exists
            if (!$user) {
                return response()->json(['error' => __('messages.no_account')], 404);
            }

            // Check if the provided password matches the hashed password
            if (!Hash::check($request->password, $user->password)) {
                return response()->json(['error' => __('messages.incorrect_password')], 401);
            }

            // Generate a token for the authenticated user
            $token = $user->createToken('authToken')->plainTextToken;

            // Return a response with the token
            return response()->json(['message' => __('messages.login_success'), 'token' => $token], 200);
        }


    public function validateToken(Request $request)
        {
            try {
                $token = $request->input('token');

                if (!$token) {
                    return response()->json(['error' => 'Token not provided'], 400);
                }

                // Attempt to parse and verify the JWT token
                $user = JWTAuth::parseToken()->authenticate();

                // If the token is valid, return a success response
                return response()->json(['message' => 'Token is valid', 'user' => $user]);
            } catch (JWTException $e) {
                // Token is invalid
                return response()->json(['error' => 'Token is invalid'], 401);
            }
        }
    public function forgotPassword(Request $request)
        {
            $request->validate(['email' => 'required|email']);

            $user = User::where('email', $request->email)->first();

            if (!$user) {
                return response()->json(['message' => 'User does not exist'], 404);
            }

            // Generate OTP
            $otp = rand(100000, 999999);
            $user->otp = $otp;
            $user->save();

            try {
                // Attempt to send the OTP via email
                Mail::to($user->email)->send(new ResetPasswordOtpMail($otp)); // Ensure ResetPasswordOtpMail is correctly set up
            } catch (\Exception $e) {
                \Log::error('Failed to send OTP: ' . $e->getMessage()); // Log the detailed error
                return response()->json(['message' => 'Failed to send OTP. Please try again.'], 500);
            }

            return response()->json(['message' => 'OTP sent to your email']);
        }



    public function resetPassword(Request $request)
        {
            $request->validate([
                'otp' => 'required',
                'newPassword' => 'required|min:8'
            ]);

            $user = User::where('otp', $request->otp)->first();

            if (!$user) {
                return response()->json(['message' => 'Invalid OTP'], 400);
            }

            $user->password = Hash::make($request->newPassword);
            $user->otp = null; // Clear OTP after use
            $user->save();

            return response()->json(['message' => 'Password has been reset successfully']);
        }



        public function update(Request $request)
        {
            $request->validate([
                'name' => 'required|string|max:255',
                'phone' => 'required|string|max:15',
                // 'email' => 'since email is not being changed',
            ]);

            try {
                $user = $request->user();
                $user->name = $request->name;
                $user->phone = $request->phone;
                $user->save();

                return response()->json(['message' => 'Profile updated successfully!', 'user' => $user]);
            } catch (\Exception $e) {
                return response()->json(['message' => 'Failed to update profile', 'error' => $e->getMessage()], 500);
            }
        }

    public function logout(Request $request)
        {
            // Revoke the token that was used to authenticate the current request...
            $request->user()->currentAccessToken()->delete();

            // Alternatively, if you want to revoke all tokens for the user
            // $request->user()->tokens()->delete();

            return response()->json(['message' => 'You have been successfully logged out.'], 200);
        }

    public function store(Request $request)
        {
            $validator = Validator::make($request->all(), [
                'name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'message' => 'required',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->errors(), 400);
            }

            $contact = Contact::create($request->all());

            return response()->json(['message' => 'Message sent successfully!', 'data' => $contact], 201);
        }


        public function uploadPrescription(Request $request) {
            putenv("PATH=" . getenv("PATH") . ";C:\\Program Files\\Tesseract-OCR");

            $request->validate(['image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',]);

            $imageName = time() . '.' . $request->image->extension();
            $request->image->move(public_path('images'), $imageName);
            Log::info("Image uploaded: {$imageName}");

            $pathToImage = public_path('images') . '/' . $imageName;

            try {
                $text = (new TesseractOCR($pathToImage))
                            ->executable('C:\\Program Files\\Tesseract-OCR\\tesseract.exe')
                            ->run();
                Log::info("OCR output: {$text}");
            } catch (\Exception $e) {
                Log::error("OCR processing failed: " . $e->getMessage());
                return response()->json(['error' => 'OCR processing failed'], 500);
            }

            $medicineNames = $this->extractMedicineNames($text);

            $response = [
                'found' => [],
                'notFoundAndAlternatives' => [],
            ];

            foreach ($medicineNames as $name) {
                $medicine = Medicine::where('name', $name)->first();

                if ($medicine && $medicine->stock > 0) {
                    // Medicine is available
                    $response['found'][] = [
                        'name' => $medicine->name,
                        'description' => $medicine->description,
                        'stock' => $medicine->stock,
                        'status' => 'Available',
                    ];
                } else {
                    // Medicine is not available or stock is 0, attempt to find an alternative
                    $alternative = $medicine ? $medicine->alternativeMedicine : null;

                    if (!$alternative) {
                        // If there's no direct alternative, try finding any alternative
                        $alternative = Medicine::whereHas('alternativeMedicine', function ($query) use ($name) {
                            $query->where('name', $name);
                        })->first();
                    }

                    if ($alternative && $alternative->stock > 0) {
                        // Alternative medicine is available
                        $response['notFoundAndAlternatives'][] = [
                            'notFoundName' => $name,
                            'alternative' => [
                                'name' => $alternative->name,
                                'description' => $alternative->description,
                                'stock' => $alternative->stock,
                                'status' => 'Available as an alternative',
                            ],
                        ];
                    } else {
                        // No alternative found or alternative is also out of stock
                        $response['notFoundAndAlternatives'][] = [
                            'notFoundName' => $name,
                            'alternative' => null,
                        ];
                    }
                }
            }

            return response()->json($response);
        }

        protected function extractMedicineNames($text) {
            $lines = explode("\n", $text);
            $medicineNames = [];

            foreach ($lines as $line) {
                if (strpos($line, '-') === 0) {
                    if (preg_match('/-\s*([\w\s]+)\s\d+mg/', $line, $matches)) {
                        $medicineName = trim($matches[1]);
                        $medicineNames[] = $medicineName;
                    }
                }
            }

            return array_unique($medicineNames);
        }


        public function product()
        {
            $medicines = Medicine::paginate(12);
            foreach ($medicines as $medicine) {
                $medicine->image_url = asset($medicine->image_url);
            }

            return response()->json($medicines);
        }


        public function show($medicineId)
        {
            $medicine = Medicine::find($medicineId);

            if (!$medicine) {
                return response()->json(['error' => 'Medicine not found'], 404);
            }
            $medicine->image_url = asset($medicine->image_url);

            return response()->json($medicine);
        }

        public function add(Request $request, $productId)
        {
            $user = $request->user(); // Get the authenticated user

            // Check if product exists
            $product = Product::find($productId);
            if (!$product) {
                return response()->json(['message' => 'Product not found'], 404);
            }

            // Check if already favorited
            $favorite = Favorite::where('user_id', $user->id)->where('product_id', $productId)->first();
            if ($favorite) {
                return response()->json(['message' => 'Already in favorites'], 409);
            }

            // Add to favorites
            Favorite::create([
                'user_id' => $user->id,
                'product_id' => $productId,
            ]);

            return response()->json(['message' => 'Added to favorites']);
        }

        public function listFavorites()
        {
            $user = auth()->user();
            $favorites = $user->favorites()->with('product')->get();
            return response()->json($favorites);
        }

        public function removeFromFavorites($id)
        {
            $user = auth()->user();
            $user->favorites()->where('product_id', $id)->delete();
            return response()->json(['message' => 'Product removed from favorites.']);
        }

        public function clearAll()
        {
            $user = auth()->user();
            $user->favorites()->delete(); // Assuming you have a favorites relationship set up in the User model

            return response()->json(['message' => 'All favorites cleared successfully'], 200);
        }


        public function addToCart(Request $request) {
            $validated = $request->validate([
                'product_id' => 'required|integer|exists:medicines,id',
                'quantity' => 'required|integer|min:1', // Ensure quantity is always at least 1
            ]);

            $user = auth()->user();
            $product_id = $validated['product_id'];
            $quantity = $validated['quantity'];

            // Find an existing cart item for the user and product
            $cartItem = Cart::where('user_id', $user->id)->where('product_id', $product_id)->first();

            if ($cartItem) {
                // If the cart item exists, update its quantity
                $cartItem->increment('quantity', $quantity);
            } else {
                // If the cart item doesn't exist, create a new one with the given quantity
                Cart::create([
                    'user_id' => $user->id,
                    'product_id' => $product_id,
                    'quantity' => $quantity,
                ]);
            }

            return response()->json(['message' => 'Product added to cart successfully!'], 200);
        }



        public function getCart() {
            $user = auth()->user();
            $cartItems = Cart::where('user_id', $user->id)->with('product')->get(); // Assuming a relationship exists

            return response()->json($cartItems);
        }

        public function updateQuantity(Request $request, $cartItemId) {
            $user = auth()->user();

            // Find the cart item ensuring it belongs to the current user
            $cartItem = Cart::where('id', $cartItemId)->where('user_id', $user->id)->first();

            if (!$cartItem) {
                return response()->json(['message' => 'Cart item not found'], 404);
            }

            // Update the quantity
            $cartItem->update([
                'quantity' => $request->input('quantity'),
            ]);

            return response()->json(['message' => 'Cart item updated successfully']);
        }

        public function removeItem($cartItemId) {
            $user = auth()->user();

            // Find the cart item ensuring it belongs to the current user
            $cartItem = Cart::where('id', $cartItemId)->where('user_id', $user->id)->first();

            if (!$cartItem) {
                return response()->json(['message' => 'Cart item not found'], 404);
            }

            // Delete the item
            $cartItem->delete();

            return response()->json(['message' => 'Cart item removed successfully']);
        }

        public function clearCart()
        {
            $user = Auth::user();  // Retrieve the authenticated user

            if (!$user) {
                return response()->json(['message' => 'User not authenticated'], 401);
            }

            // Retrieve all cart items of the user and delete them
            $user->carts()->delete(); // Make sure the relation in User model is 'carts' if it handles multiple items

            return response()->json(['message' => 'Cart cleared successfully'], 200);
        }


        public function getCartQuantity(Request $request)
        {
            $user = $request->user();



            // First, ensure that we're working with the user who has items in their cart.
            $totalQuantity = Cart::where('user_id', $user->id)->sum('quantity');

            // The sum method will automatically calculate the total quantity of all items
            // belonging to the user. If there are no items, sum will return 0.

            return response()->json(['quantity' => $totalQuantity]);
        }


        public function getPaymobToken() {
            $apiKey = env('PAYMOB_API_KEY');
            $response = Http::post('https://accept.paymob.com/api/auth/tokens', [
                'api_key' => $apiKey,
            ]);

            return $response->json()['token'];
        }
        public function createPaymobOrder($authToken, $orderAmount) {
            $response = Http::post('https://accept.paymob.com/api/ecommerce/orders', [
                'auth_token' => $authToken,
                'delivery_needed' => "false",
                'merchant_id' => env('PAYMOB_MERCHANT_ID'), // You get this from your Paymob dashboard
                'amount_cents' => $orderAmount * 100, // Convert to cents as required by Paymob
                'currency' => 'EGP',
                'items' => [], // Add items here if necessary
            ]);

            return $response->json()['id']; // This is the order ID on Paymob's side
        }

        public function getPaymentKey($authToken, $orderId, $amount) {

            $response = Http::post('https://accept.paymob.com/api/acceptance/payment_keys', [
                'auth_token' => $authToken,
                'expiration' => 3600, // Token expiry in seconds
                'order_id' => $orderId,
                'amount_cents' => $amount * 100, // Convert to cents
                'billing_data' => [
                    "apartment" => "NA",
                    "email" => "test@example.com",
                    "floor" => "NA",
                    "first_name" => "Test",
                    "street" => "NA",
                    "building" => "NA",
                    "phone_number" => "+201000000000",
                    "shipping_method" => "NA",
                    "postal_code" => "NA",
                    "city" => "Cairo",
                    "country" => "EG",
                    "last_name" => "Account",
                    "state" => "Cairo"
                ],
                'currency' => 'EGP',
                'integration_id' => env('PAYMOB_INTEGRATION_ID'), // Your Paymob integration ID
            ]);


            return $response->json()['token'];
        }


        public function checkout(Request $request)
        {
            Log::info($request->all()); // Log the incoming request data for debugging

            $user = auth()->user(); // Assuming you're using a method to authenticate users

            // Validate the incoming request
            $validated = $request->validate([
                'address' => 'required|string',
                'paymentMethod' => 'required|in:cash,card',
                'cartItems' => 'required|array',
                'cartItems.*.product_id' => 'required|exists:medicines,id',
                'cartItems.*.quantity' => 'required|integer|min:1',
            ]);

            // Calculate total price of the order
            $totalPrice = collect($validated['cartItems'])->sum(function ($item) {
                // Assuming you have a method to get the price of the item
                $product = Medicine::find($item['product_id']);
                return $product->price * $item['quantity'];
            });

            DB::beginTransaction();

            try {
                // Create the order
                $order = Order::create([
                    'user_id' => $user->id,
                    'address' => $validated['address'],
                    'payment_method' => $validated['paymentMethod'],
                    'total_price' => $totalPrice,
                ]);

                // Create order items
                foreach ($validated['cartItems'] as $item) {
                    $product = Medicine::find($item['product_id']);
                    $order->items()->create([
                        'medicine_name' => $product->name,
                        'medicine_name_ar' => $product->name_ar,
                        'quantity' => $item['quantity'],
                        'price' => $product->price,
                        'payment_method' => $validated['paymentMethod'],
                    ]);
                }
                DB::commit();

                if ($validated['paymentMethod'] === 'card') {
                    // Simulate generating a payment URL
                    $authToken = $this->getPaymobToken();
                    $paymobOrderId = $this->createPaymobOrder($authToken, $totalPrice);
                    $paymentKey = $this->getPaymentKey($authToken, $paymobOrderId, $totalPrice);

                    $paymentUrl = "https://accept.paymob.com/api/acceptance/iframes/" . env('PAYMOB_IFRAME_ID') . "?payment_token=$paymentKey";
                    return response()->json(['paymentUrl' => $paymentUrl]);
                } else {
                    // Optionally clear the user's cart
                    Cart::where('user_id', $user->id)->delete();
                    return response()->json(['message' => 'Order placed successfully']);
                }
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Order placement failed: ' . $e->getMessage());
                return response()->json(['error' => 'Order placement failed'], 500);
            }
        }
        public function handleCallback(Request $request)
        {
            \Log::info($request->all());

            // Example of verifying payment (You should replace this with actual verification logic)
            $paymentStatus = $request->input('status');
            $orderId = $request->input('order_id'); // Ensure you send this from Paymob

            // Find the order by ID
            $order = Order::find($orderId);

            if (!$order) {
                \Log::error("Order not found: {$orderId}");
                return response()->json(['error' => 'Order not found'], 404);
            }

            if ($paymentStatus === 'SUCCESS') {
                // Update order status to complete or any other logic
                $order->update(['status' => 'completed']);

                // Optionally, clear the user's cart
                Cart::where('user_id', $order->user_id)->delete();

                return response()->json(['message' => 'Payment successful, order processed']);
            } else {
                // Handle payment failure (e.g., mark order as payment_failed)
                $order->update(['status' => 'payment_failed']);
                return response()->json(['error' => 'Payment failed']);
            }
        }
        public function checkPaymentStatus($paymentId)
        {
            // Your logic to check payment status using $paymentId
            // This is a simplified example
            $payment = Payment::find($paymentId); // Assuming you have a Payment model

            if (!$payment) {
                return response()->json(['status' => 'error', 'message' => 'Payment not found']);
            }

            return response()->json(['status' => $payment->status]);
        }


        public function initiateDonation(Request $request)
        {
            $validated = $request->validate([
                'amount' => 'required|numeric|min:1',
                'donor_name' => 'sometimes|string',
            ]);

            $authToken = $this->getPaymobToken();
            $paymobOrderId = $this->createPaymobOrder($authToken, $validated['amount']);
            $paymentKey = $this->getPaymentKey($authToken, $paymobOrderId, $validated['amount']);

            $paymentUrl = "https://accept.paymob.com/api/acceptance/iframes/" . env('PAYMOB_IFRAME_ID') . "?payment_token=$paymentKey";

            // Optionally, save the donation to your database here

            return response()->json(['paymentUrl' => $paymentUrl]);
        }

        public function getOrders(Request $request)
        {
            $user = auth()->user();
            $orders = $user->orders()->with('items')->get(); // Adjust based on your database relationships

            return response()->json($orders);
        }

        public function storeFeedback(Request $request)
        {
            $validated = $request->validate([
                'name' => 'required',
                'email' => 'required|email',
                'feedback' => 'required|string',
                'rating' => 'required|integer|min:1|max:5',
            ]);

            $feedback = Feedback::create($validated);

            return response()->json(['message' => 'Feedback received', 'feedback' => $feedback], 201);
        }

        public function index()
        {
            $feedback = Feedback::all();
            return response()->json($feedback);
        }

        public function getByCategory($categorySlug)
        {
            $products = Product::where('category', $categorySlug)->get();
            return response()->json($products);
        }


        public function uploadMedicalTest(Request $request) {
            $request->validate([
                'file' => 'required|file|max:10240',
            ]);

            $image = $request->file('file');
            $imagePath = $image->store('uploads', 'public');

            $pythonExecutable = "C:\\Users\\DELL\\venv\\Scripts\\python.exe";
            $scriptPath = 'E:\\Graduation Project\\Pharmacy-Back-End\\public\\scripts\\analyze_lab_results.py';
            $absoluteImagePath = storage_path('app/public/' . $imagePath);

            // Ensure the PATH includes the directory for Tesseract OCR
            $process = new Process([
                $pythonExecutable,
                $scriptPath,
                $absoluteImagePath
            ], null, ['PATH' => getenv('PATH') . ';C:\\Program Files\\Tesseract-OCR']);

            $process->run();

            if (!$process->isSuccessful()) {
                \Log::error("Error executing Python script: " . $process->getErrorOutput());
                throw new ProcessFailedException($process);
            }

            $result = json_decode($process->getOutput(), true);
            return response()->json($result);
        }

        public function storeRareMedicine(Request $request)
        {
            $request->validate([
                'address' => 'required',
                'medicine_name' => 'required',
                'quantity' => 'required|integer',
            ]);

            $user = Auth::user(); // Get the authenticated user

            if (!$user) {
                return response()->json(['message' => 'No authenticated user found'], 401);
            }

            RareMedicineRequest::create([
                'name' => $user->name, // Use the name from the authenticated user
                'phone' => $user->phone, // Use the phone from the authenticated user
                'address' => $request->address,
                'medicine_name' => $request->medicine_name,
                'quantity' => $request->quantity,
            ]);

            return response()->json(['message' => 'Request submitted successfully'], 200);
        }


}
