<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Channel;
use App\Models\Search;
use App\Models\Video;
use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Show search list
     */
    public function index($query)
    {
        $search = Search::where('query', $query)->with(['videos.channel'])->first();
        $_videos = $search->videos()->get();
        $videos = array();
        foreach ($_videos as $video){
           array_push($videos, (object) [
                'id' => $video->id,
                'title' => $video->title,
                'link' => $video->link,
                'preview' => $video->preview,
                'favorite' => $video->favorite,
                'channel' =>  $video->channel['name'],
                'channelLink' =>  $video->channel['link'],
                'published_at' => $video->published_at,
            ]);
        }
        $search = $search->query;
        return json_encode(['videos' => $videos, 'search' => $search],JSON_UNESCAPED_UNICODE);
    }

    /**
     * Show favorites video list
     */
    public function favorites()
    {
        $_videos = Video::where('favorite', 1)->with(['channel'])->get();
        $videos = array();
        foreach ($_videos as $video) {
            array_push($videos, (object) [
                'id' => $video->id,
                'title' => $video->title,
                'link' => $video->link,
                'preview' => $video->preview,
                'favorite' => $video->favorite,
                'channel' =>  $video->channel['name'],
                'channelLink' =>  $video->channel['link'],
            ]);
        }
        $search = 'Любимые';
        return  (json_encode(['videos' => $videos, 'search' => $search],JSON_UNESCAPED_UNICODE));
    }

    /**
     * Get answer on query and store in DB
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function store(Request $request)
    {
        $search = Search::where('query', $request->search)->first();

        if (!isset($search->id)) {
//            dd('Поиск запрещён');
            $client = new \Google_Client();
            $client->setApplicationName(env('APP_NAME'));
            $client->setScopes([
                'https://www.googleapis.com/auth/youtube.force-ssl',
            ]);
            $client->setDeveloperKey(config('youtube.youtube_api'));

            $service = new \Google_Service_YouTube($client);
            $optParams = array(
                'maxResults' => 50,
                'q' => $request->search,
                'type' => 'video'
            );
            $results = $service->search->listSearch('snippet', $optParams);
            $search = Search::create(['query' => $request->search]);

            $videos = [];
            foreach ($results as $result) {
                $channelRow = [
                    'name' => $result->snippet->channelTitle,
                    'link' => 'https://www.youtube.com/channel/' . $result->snippet->channelId
                ];
                if (!Channel::where('name', $channelRow['name'])->first()) {
                    $channel = Channel::create($channelRow);
                }
                $resultRow = [
                    'title' => $result->snippet->title,
                    'link' => 'https://www.youtube.com/watch?v=' . $result->id->videoId,
                    'preview' => $result->snippet->thumbnails->high->url,
                    'channel_id' => $channel->id,
                    'published_at' => str_replace('Z', ' ', str_replace('T', ' ',$result->snippet->publishedAt))
                ];
                $videosRes = $search->videos()->create($resultRow);
                array_push($videos, collect($resultRow));
//                $searchRes = SearchingResult::create($resultRow);
//                $searchRes = SearchingResult::find($searchRes->id);

//                $channel = $searchRes->channel()->create($channelRow);
//                $channel = Channel::create($channelRow);
//                dump($channel);
//                dump($searchRes);

            }
        }

//        TODO сделать отдельный метод index для получения страниц выдачи
        return redirect()->route('search', ['query' => $request->search]);
    }




    /**
     * Update row
     * @param Request $request
     * @return false|string
     */
    public function update(Request $request)
    {
        $video = Video::find($request->id)->update(['favorite' => $request->like]);
        return json_encode($video);
    }
}
