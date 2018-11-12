import React, {Component} from 'react';
import PropTypes from "prop-types";
import elasticlunr from "elasticlunr";

class SearchComponent extends Component {
    static propTypes = {
        job: PropTypes.shape({
            config: PropTypes.shape({
                password: PropTypes.any,
                id: PropTypes.any
            }),
            rows: PropTypes.array,
            rowsDictionary: PropTypes.any
        }),
    };

    searchEngine = elasticlunr(function () {
        this.addField('content');
        this.setRef('id');
    });

    constructor(props) {
        super(props);
        this.state = {
            elements: [],
            mapElements: {},
            searchTxt: ''
        };
    }


    componentDidMount() {
    }

    componentWillUnmount() {

    }
    static getDerivedStateFromProps = (props,state) =>{
        let elements = [];
        let mapElements = {};
        props.job.rows.map((row, index) => {
            const source = {
                content: row.source.content_clean,
                type: 'source',
                id: row.source.id,
                index: index,
            };
            const target = {
                content: row.target.content_clean,
                type: 'target',
                id: row.target.id,
                index: index,
            };
            mapElements[row.source.id] = source;
            mapElements[row.target.id] = target;
            elements.push(source,target);
        });

        state.elements = elements;
        state.mapElements = mapElements;
        return state;
    };

    shouldComponentUpdate = (nextProps,nextState) =>{
        this.state.elements.map(e=>{
           this.searchEngine.removeDoc(e)
        });
        nextState.elements.map(e=>{
            this.searchEngine.addDoc(e);
        });
        return true;
    };

    render() {
        return (
            <div id="search">
                <input type="text" value={this.state.searchTxt} onChange={this.onSearch}/>
            </div>
        );
    }

    onSearch = (event) =>{
        let search = this.searchEngine.search(event.target.value,{expand: true});

        search.sort((a,b)=>{
            return parseInt(a.ref) - parseInt(b.ref);
        });
        
        search.map(item =>{
           console.log(this.state.mapElements[item.ref]);
        });
        console.log("\n \n \n \n")
        this.setState({
            searchTxt: event.target.value
        })
    }
}

export default SearchComponent;
